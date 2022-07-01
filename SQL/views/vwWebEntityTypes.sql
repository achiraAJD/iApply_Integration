USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebEntityTypes]    Script Date: 1/07/2022 11:47:07 AM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO


ALTER VIEW [dbo].[vwWebEntityTypes]
AS
SELECT        ET_ID, ET_Code, ET_Desc
FROM            dbo.EntityTypes

GO


