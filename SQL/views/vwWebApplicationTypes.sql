USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebApplicationTypes]    Script Date: 1/07/2022 11:41:59 AM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

ALTER View [dbo].[vwWebApplicationTypes]
as

	SELECT ApplicationTypes.* 
	FROM ApplicationTypes
	
;
GO


